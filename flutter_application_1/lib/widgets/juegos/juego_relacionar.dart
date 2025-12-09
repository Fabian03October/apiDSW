import 'package:flutter/material.dart';

class JuegoRelacionar extends StatefulWidget {
  final Map<String, dynamic> datos;
  final Function(String respuesta) onRespuestaChanged;

  const JuegoRelacionar({
    super.key, 
    required this.datos,
    required this.onRespuestaChanged
  });

  @override
  State<JuegoRelacionar> createState() => _JuegoRelacionarState();
}

class _JuegoRelacionarState extends State<JuegoRelacionar> {
  String? seleccionadoIzquierda;
  final Map<String, String> uniones = {}; 

  // NUEVO: Listas separadas para poder revolverlas
  List<String> listaIzquierda = [];
  List<String> listaDerecha = [];

  @override
  void initState() {
    super.initState();
    // 1. Extraemos los pares originales de la base de datos
    List<dynamic> paresOriginales = widget.datos['pares'] ?? [];

    // 2. Llenamos las listas izquierda y derecha
    for (var item in paresOriginales) {
      listaIzquierda.add(item['origen'].toString());
      listaDerecha.add(item['destino'].toString());
    }

    // 3. ¡EL TRUCO! Revolvemos aleatoriamente la columna derecha
    listaDerecha.shuffle();
  }

  void _notificarRespuesta() {
    String respuesta = uniones.entries.map((e) => "${e.key} -> ${e.value}").join(", ");
    widget.onRespuestaChanged(respuesta);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const Text("Relaciona las columnas:", style: TextStyle(color: Colors.grey)),
        const SizedBox(height: 15),
        
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          crossAxisAlignment: CrossAxisAlignment.start, // Alineación superior
          children: [
            // --- COLUMNA IZQUIERDA (ORIGEN - Fija) ---
            Expanded(
              child: Column(
                // Iteramos sobre la lista izquierda preparada en initState
                children: listaIzquierda.map((val) {
                  bool unido = uniones.containsKey(val);
                  return GestureDetector(
                    onTap: () {
                      if(unido) {
                        setState(() => uniones.remove(val));
                        _notificarRespuesta();
                      } else {
                        setState(() => seleccionadoIzquierda = val);
                      }
                    },
                    child: _item(val, unido ? Colors.green.shade100 : (seleccionadoIzquierda == val ? Colors.blue.shade100 : Colors.white)),
                  );
                }).toList(),
              ),
            ),

            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 10),
              child: Icon(Icons.swap_horiz, color: Colors.grey),
            ),

            // --- COLUMNA DERECHA (DESTINO - Revuelta) ---
            Expanded(
              child: Column(
                // Iteramos sobre la lista derecha que YA ESTÁ REVUELTA
                children: listaDerecha.map((val) {
                  bool unido = uniones.containsValue(val);
                  return GestureDetector(
                    onTap: () {
                      if (seleccionadoIzquierda != null && !unido) {
                        setState(() => uniones[seleccionadoIzquierda!] = val);
                        seleccionadoIzquierda = null;
                        _notificarRespuesta();
                      }
                    },
                    child: _item(val, unido ? Colors.green.shade100 : Colors.white),
                  );
                }).toList(),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _item(String text, Color color) {
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8),
      padding: const EdgeInsets.all(12),
      constraints: const BoxConstraints(minHeight: 60), // Altura mínima uniforme
      decoration: BoxDecoration(
        color: color,
        border: Border.all(color: Colors.grey.shade400),
        borderRadius: BorderRadius.circular(12),
        boxShadow: [BoxShadow(color: Colors.grey.shade200, blurRadius: 4, offset: const Offset(0, 2))]
      ),
      child: Center(child: Text(text, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w500))),
    );
  }
}